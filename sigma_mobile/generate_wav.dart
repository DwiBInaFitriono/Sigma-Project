import 'dart:io';
import 'dart:typed_data';
import 'dart:math';

void main() {
  final sampleRate = 44100;
  final duration = 0.5;
  final freq = 440.0;
  final numSamples = (sampleRate * duration).toInt();
  
  final bytes = BytesBuilder();
  
  // RIFF
  bytes.add('RIFF'.codeUnits);
  final fileSize = 36 + numSamples * 2;
  bytes.add(Uint32List.fromList([fileSize]).buffer.asUint8List());
  bytes.add('WAVE'.codeUnits);
  
  // fmt 
  bytes.add('fmt '.codeUnits);
  bytes.add(Uint32List.fromList([16]).buffer.asUint8List());
  bytes.add(Uint16List.fromList([1, 1]).buffer.asUint8List()); // PCM, 1 channel
  bytes.add(Uint32List.fromList([sampleRate, sampleRate * 2]).buffer.asUint8List());
  bytes.add(Uint16List.fromList([2, 16]).buffer.asUint8List()); // 2 block align, 16 bits
  
  // data
  bytes.add('data'.codeUnits);
  bytes.add(Uint32List.fromList([numSamples * 2]).buffer.asUint8List());
  
  final samples = Int16List(numSamples);
  for (int i = 0; i < numSamples; i++) {
    final t = i / sampleRate;
    samples[i] = (sin(2 * pi * freq * t) * 32767).toInt();
  }
  bytes.add(samples.buffer.asUint8List());
  
  File('assets/sounds/alarm.wav').writeAsBytesSync(bytes.toBytes());
  print('WAV generated');
}
