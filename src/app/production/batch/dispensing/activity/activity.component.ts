import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {
 
  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDispensingActivities();
  }

  getDispensingActivities(){
    this.service.get('production/dispensing.php?type=getDispensingActivity').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  download(){
    this.service.open('production/dispensing.php?type=downloadDispensingActivities&id='+this.selectedResult['id']);
  }

}
