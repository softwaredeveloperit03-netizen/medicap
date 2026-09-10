import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-points',
  templateUrl: './points.component.html',
  styleUrls: ['./points.component.css']
})
export class PointsComponent implements OnInit {

  points: any = [
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Generation Point",
        "point_no": "NG-01"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "HSM-201 Plant-02",
        "point_no": "NG-02"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "PF-201 Plant-02",
        "point_no": "NG-03"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "CF-202/203 Plant-02",
        "point_no": "NG-04"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "ANFD-2011 Plant-02",
        "point_no": "NG-05"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "R-202/205/206/207 Plant-02",
        "point_no": "NG-06"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "HSM-301 Plant-03",
        "point_no": "NG-07"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "R-901/902/903 Plant-09",
        "point_no": "NG-08"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "R-904/906/907/908/909/910 Plant-09",
        "point_no": "NG-09"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "CF-903/904/905/906/907/908 SF-901 Plant-09",
        "point_no": "NG-10"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Crude Centrifuge area Plant-01",
        "point_no": "NG-11"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Final filtration and Drying area Plant-01",
        "point_no": "NG-12"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Drying and Blending area Plant-01",
        "point_no": "NG-13"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Crystallizer area at First Floor Plant-01",
        "point_no": "NG-14"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Filtration area Plant-01",
        "point_no": "NG-15"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reaction area at Second Floor Plant-01",
        "point_no": "NG-16"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Packing Room Ground Floor Plant-12",
        "point_no": "NG-17"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1201 Ground Floor Plant-12",
        "point_no": "NG-18"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1202 Ground Floor Plant-12",
        "point_no": "NG-19"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1203 Ground Floor Plant-12",
        "point_no": "NG-20"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1204 Ground Floor Plant-12",
        "point_no": "NG-21"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1205 Ground Floor Plant-12",
        "point_no": "NG-22"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1206 Ground Floor Plant-12",
        "point_no": "NG-23"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Centrifuge CF-1201 Ground Floor Plant-12",
        "point_no": "NG-24"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Centrifuge CF-1202 Ground Floor Plant-12",
        "point_no": "NG-25"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Sparkler Filter-1201 Ground Floor Plant-12",
        "point_no": "NG-26"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Drying area VTD-1201 Ground Floor Plant-12",
        "point_no": "NG-27"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Packing Room First Floor Plant-12",
        "point_no": "NG-28"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Centrifuge CF-1203 First Floor Plant-12",
        "point_no": "NG-29"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Centrifuge CF-1204 First Floor Plant-12",
        "point_no": "NG-30"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1207 Second Floor Plant-12",
        "point_no": "NG-31"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1208 Second Floor Plant-12",
        "point_no": "NG-32"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1209 Second Floor Plant-12",
        "point_no": "NG-33"
    },
    {
        "type": "Nitrogen Gas",
        "sampling_point": "Reactor R-1210 Second Floor Plant-12",
        "point_no": "NG-34"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Generation Point",
        "point_no": "CA-01"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "FP-302 Plant-03",
        "point_no": "CA-02"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "FP-303 Plant-03",
        "point_no": "CA-03"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "ANF-301/302/303 Plant-03",
        "point_no": "CA-04"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-03",
        "point_no": "CA-05"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "PF-402/404 Plant-04",
        "point_no": "CA-06"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "PF-403 Plant-04",
        "point_no": "CA-07"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-04",
        "point_no": "CA-08"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "SF-501 Plant-05",
        "point_no": "CA-09"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-05",
        "point_no": "CA-10"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "PF-601 Plant-06",
        "point_no": "CA-11"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-06",
        "point_no": "CA-12"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Filter Cleaning Area Plant-02",
        "point_no": "CA-13"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-02",
        "point_no": "CA-14"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Filter Cleaning Area Plant-09",
        "point_no": "CA-15"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "P.P. Area Plant-09",
        "point_no": "CA-16"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Wash room area Plant-01",
        "point_no": "CA-17"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Near Crystallizer area at 1st Floor Plant-01",
        "point_no": "CA-18"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Filter cleaning room Plant-01",
        "point_no": "CA-19"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Near Reactor area at 2nd Floor Plant-01",
        "point_no": "CA-20"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Reactor Area Ground Floor Plant-12",
        "point_no": "CA-21"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Drying Area Ground Floor Plant-12",
        "point_no": "CA-22"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Packing Area Ground Floor Plant-12",
        "point_no": "CA-23"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Wash Area Ground Floor Plant-12",
        "point_no": "CA-24"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Packing Room First Floor Plant-12",
        "point_no": "CA-25"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "FBD Room First Floor Plant-12",
        "point_no": "CA-26"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Washing Area First Floor Plant-12",
        "point_no": "CA-27"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Filter Cleaning Area Second Floor Plant-12",
        "point_no": "CA-28"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Water System Area Second Floor Plant-12",
        "point_no": "CA-29"
    },
    {
        "type": "Compressed Air",
        "sampling_point": "Reactor Area Second Floor Plant-12",
        "point_no": "CA-30"
    }
  ];
  constructor() { }

  ngOnInit(): void {
  }

}
