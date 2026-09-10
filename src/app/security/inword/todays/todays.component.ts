import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-todays',
  templateUrl: './todays.component.html',
  styleUrls: ['./todays.component.css']
})
export class TodaysComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  challan_no='';
  po_no='';
  vendor_no= '';
  vendors;
  vendor_name='';
  todays;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingChallans();
  }

  getPendingChallans(){
    this.service.get('store/challan.php?type=getPendingChallans').subscribe(response => {
      this.results = response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
