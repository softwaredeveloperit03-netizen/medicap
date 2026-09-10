import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css']
})
export class StartComponent implements OnInit {
  results;
  batch_no ='';
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingBatches();
  }

  getPendingBatches(){
    this.service.get('production/lot/manufacturing.php?type=getPendingBatches').subscribe(response=>{
      this.results=response;
    });
  }

  viewBatch(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  start(){
    let temp=this.selectedResult;
    temp['batch_no']=this.batch_no;
    this.service.post('production/lot/manufacturing.php?type=startProduction&id='+this.selectedResult['id'] +'&batch_no=' +this.batch_no,JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.isView=false;
        this.getPendingBatches();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}
