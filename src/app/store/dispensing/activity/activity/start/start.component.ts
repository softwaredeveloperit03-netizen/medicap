import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css']
})
export class StartComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];
  laf_pressure='';
  rlaf_id='RLAF-02';
  constructor(private service:DataAccessService) {
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
  }

  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getAcceptedRequests').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  startRLAF(){
    this.service.get('store/dispensing.php?type=startRLAF&laf_id='+this.rlaf_id + '&laf_pressure=' +this.laf_pressure +'&id='+this.selectedResult['id']).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('RLAF start successfuly');
        this.getAcceptedRequests();
        this.isView=false;
      }else{
        alertify.error('some error occured!');
      }
    });
  }
 
}
