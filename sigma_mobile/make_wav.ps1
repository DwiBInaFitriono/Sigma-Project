$path = "d:\Project Laravel\Sigma-Project\sigma_mobile\assets\sounds\alarm.wav"
$sampleRate = 44100
$duration = 1.0 # seconds
$freq = 440.0 # Hz
$numSamples = [int]($sampleRate * $duration)
$stream = [System.IO.File]::Create($path)
$writer = New-Object System.IO.BinaryWriter($stream)

# RIFF chunk
$writer.Write([char[]]'RIFF')
$writer.Write([int](36 + $numSamples * 2))
$writer.Write([char[]]'WAVE')

# fmt chunk
$writer.Write([char[]]'fmt ')
$writer.Write([int]16)
$writer.Write([short]1)
$writer.Write([short]1)
$writer.Write([int]$sampleRate)
$writer.Write([int]($sampleRate * 2))
$writer.Write([short]2)
$writer.Write([short]16)

# data chunk
$writer.Write([char[]]'data')
$writer.Write([int]($numSamples * 2))

for ($i = 0; $i -lt $numSamples; $i++) {
    $t = $i / $sampleRate
    $sample = [short]([Math]::Sin(2 * [Math]::PI * $freq * $t) * 32767)
    $writer.Write($sample)
}

$writer.Close()
