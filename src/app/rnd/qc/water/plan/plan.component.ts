import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan',
  templateUrl: './plan.component.html',
  styleUrls: ['./plan.component.css']
})
export class PlanComponent implements OnInit {
  results;

  keys;
  constructor( private service:DataAccessService) { }

  ngOnInit(): void {
    this.getSamplingPlan()
  }
  getSamplingPlan(){
    this.service.get('qc/water.php?type=getSamplingPlan').subscribe(response=>{
      this.results=response;
      let temp = this.results[0];
      this.keys = Object.keys(temp);

      for (let i = 0; i < this.keys.length; i++) {
        let key = this.keys[i];
        if (key == 'point_no') {
          this.keys.splice(i, 1);
        }
        if (key == 'point_name') {
          this.keys.splice(i, 1);
        }
      }

      for (let i = 0; i < this.keys.length; i++) {
        let key = this.keys[i];
        if (key == 'point_name') {
          this.keys.splice(i, 1);
        }
      }
    })
  }
}
