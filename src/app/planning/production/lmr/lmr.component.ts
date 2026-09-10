import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-lmr',
  templateUrl: './lmr.component.html',
  styleUrls: ['./lmr.component.css'],
  providers:[DatePipe]
})
export class LmrComponent implements OnInit {
  results;
  isView = false;
  selectedResult=[];
  selectedIndex = -1;
  from_date='';
  to_date='';
  constructor(private service: DataAccessService,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getStartedBatches();
  }
  getStartedBatches(){
    this.service.get('production/lot/manufacturing.php?type=getStartedBatches&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.selectedResult = this.results[this.selectedIndex];
        this.isView = true;
      } else {
        this.isView = false;
      }
    });
  }
  download(){
    this.service.open('production/lot/manufacturing.php?type=downloadStartedBatches&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  view(index){
    this.selectedIndex = index;
    this.selectedResult=this.results[index];
    this.isView = true;
  }

  start(stage, index) {
    this.service.get('production/lot/manufacturing.php?type=startStage&id=' + this.selectedResult['id'] + '&stage=' + stage + '&index=' + index).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Started Succcessfully!');
        this.getStartedBatches();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  complete(stage, index) {
    index = index + 1;
    if (+index == +this.selectedResult['stages'].length) {
      this.selectedIndex = -1;
      index = 'last';
    }
    this.service.get('production/lot/manufacturing.php?type=completeStage&id=' + this.selectedResult['id'] + '&stage=' + stage + '&index=' + index).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Completed Succcessfully!');
        this.getStartedBatches();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
