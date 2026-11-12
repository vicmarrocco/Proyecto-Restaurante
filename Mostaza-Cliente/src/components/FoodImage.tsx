import React, { useState } from 'react';
import { Image, StyleProp, Text, View, ViewStyle } from 'react-native';
import { MostazaColors } from '@/constants/Colors';

export default function FoodImage({ uri, style, emoji = '🍔' }: { uri?: string; style?: StyleProp<ViewStyle>; emoji?: string }) {
  const [failed, setFailed] = useState(false);
  if (!uri || failed) {
    return (
      <View style={[{ backgroundColor: MostazaColors.amarilloClaro, alignItems: 'center', justifyContent: 'center' }, style]}>
        <Text style={{ fontSize: 34 }}>{emoji}</Text>
      </View>
    );
  }
  return <Image source={{ uri }} style={style as any} resizeMode="cover" onError={() => setFailed(true)} />;
}
