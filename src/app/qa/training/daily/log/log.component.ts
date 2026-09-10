import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  selectedNeed=[];
   training = [];
   isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getdailytraining();
   }

   
  getdailytraining() {
    this.service.get('training.php?type=getdailytraininglog').subscribe((response: any) => {
      this.training = response;
    });
  }


  view(index) {
    this.selectedNeed = this.training[index];
    this.isView = true;
  }

}
