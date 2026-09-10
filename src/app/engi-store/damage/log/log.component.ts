import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  total = 0;
  damages = [];
  checkPointData ;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessDamagesLog();
   }


 

 
 

  getInprocessDamagesLog() {
    this.service.get('store/raw.php?type=getInprocessDamagesLogGeneralMaterial').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.damages =[];
    this.selectedReport = this.results[index];
    this.isView = true;
   // this.total = +this.selectedReport['receiving_details'].outer_damage ;
    this.damage_details = this.selectedReport['damage_details'] ;
    
  }
 
  viewFile(url) {
    url = this.service.url + '../../upload/damage/' + url +'?v=1';
   window.open(url, '_blank');
 }
 
  damage_details =[];


}
