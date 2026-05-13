import React from "react";

export default function BarChart({data,color,height=80}){

 const max=Math.max(...data.map(d=>d.value));

 return(
  <svg width="100%" height={height}>
   {data.map((d,i)=>{
    const barH=(d.value/max)*(height-20);

    return(
     <rect
      key={i}
      x={i*30}
      y={height-barH}
      width="20"
      height={barH}
      fill={color}
     />
    )
   })}
  </svg>
 )

}