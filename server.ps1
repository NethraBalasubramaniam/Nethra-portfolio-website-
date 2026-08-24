param (
    [int]$Port = 3000
)

$root = $PSScriptRoot
$ip = [System.Net.IPAddress]::Any
$listener = $null

try {
    $listener = New-Object System.Net.Sockets.TcpListener($ip, $Port)
    $listener.Start()
    Write-Host "Server running on port $Port (http://localhost:$Port/ and http://127.0.0.1:$Port/)"
} catch {
    $Port = 8080
    $listener = New-Object System.Net.Sockets.TcpListener($ip, $Port)
    $listener.Start()
    Write-Host "Server running on port $Port (http://localhost:$Port/ and http://127.0.0.1:$Port/)"
}

$mimeTypes = @{
    ".html" = "text/html; charset=utf-8"
    ".htm"  = "text/html; charset=utf-8"
    ".css"  = "text/css; charset=utf-8"
    ".js"   = "text/javascript; charset=utf-8"
    ".json" = "application/json; charset=utf-8"
    ".png"  = "image/png"
    ".jpg"  = "image/jpeg"
    ".jpeg" = "image/jpeg"
    ".svg"  = "image/svg+xml"
    ".gif"  = "image/gif"
    ".ico"  = "image/x-icon"
    ".woff" = "font/woff"
    ".woff2"= "font/woff2"
    ".ttf"  = "font/ttf"
}

while ($true) {
    try {
        $client = $listener.AcceptTcpClient()
        $stream = $client.GetStream()
        $buffer = New-Object byte[] 8192
        $bytesRead = $stream.Read($buffer, 0, $buffer.Length)
        
        if ($bytesRead -le 0) {
            $client.Close()
            continue
        }

        $requestText = [System.Text.Encoding]::UTF8.GetString($buffer, 0, $bytesRead)
        $lines = $requestText -split "`r?`n"
        if ($lines.Length -eq 0 -or [string]::IsNullOrWhiteSpace($lines[0])) {
            $client.Close()
            continue
        }

        $tokens = $lines[0].Split(' ')
        if ($tokens.Length -lt 2) {
            $client.Close()
            continue
        }

        $rawUrl = $tokens[1]
        $decodedUrl = [System.Uri]::UnescapeDataString($rawUrl)
        if ($decodedUrl.Contains("?")) {
            $decodedUrl = $decodedUrl.Substring(0, $decodedUrl.IndexOf("?"))
        }

        if ($decodedUrl -eq "/" -or [string]::IsNullOrEmpty($decodedUrl)) {
            $decodedUrl = "/index.html"
        }

        $cleanRelPath = $decodedUrl.TrimStart('/').Replace('/', [System.IO.Path]::DirectorySeparatorChar)
        $localPath = [System.IO.Path]::Combine($root, $cleanRelPath)

        if ([System.IO.File]::Exists($localPath)) {
            $ext = [System.IO.Path]::GetExtension($localPath).ToLower()
            $contentType = if ($mimeTypes.ContainsKey($ext)) { $mimeTypes[$ext] } else { "application/octet-stream" }
            $fileBytes = [System.IO.File]::ReadAllBytes($localPath)

            $header = "HTTP/1.1 200 OK`r`nAccess-Control-Allow-Origin: *`r`nContent-Type: $contentType`r`nContent-Length: $($fileBytes.Length)`r`nConnection: close`r`n`r`n"
            $headerBytes = [System.Text.Encoding]::UTF8.GetBytes($header)
            
            $stream.Write($headerBytes, 0, $headerBytes.Length)
            $stream.Write($fileBytes, 0, $fileBytes.Length)
        } elseif ([string]::IsNullOrEmpty([System.IO.Path]::GetExtension($cleanRelPath))) {
            # SPA Fallback for routes like /dsphere, /merchant, /work, /about, /care, /vault, /console, /agent
            $indexPath = [System.IO.Path]::Combine($root, "index.html")
            $fileBytes = [System.IO.File]::ReadAllBytes($indexPath)

            $header = "HTTP/1.1 200 OK`r`nAccess-Control-Allow-Origin: *`r`nContent-Type: text/html; charset=utf-8`r`nContent-Length: $($fileBytes.Length)`r`nConnection: close`r`n`r`n"
            $headerBytes = [System.Text.Encoding]::UTF8.GetBytes($header)

            $stream.Write($headerBytes, 0, $headerBytes.Length)
            $stream.Write($fileBytes, 0, $fileBytes.Length)
        } else {
            $notFound = "HTTP/1.1 404 Not Found`r`nContent-Type: text/plain`r`nContent-Length: 13`r`nConnection: close`r`n`r`n404 Not Found"
            $notFoundBytes = [System.Text.Encoding]::UTF8.GetBytes($notFound)
            $stream.Write($notFoundBytes, 0, $notFoundBytes.Length)
        }

        $stream.Flush()
        $client.Close()
    } catch {
        # ignore transient socket errors
    }
}
