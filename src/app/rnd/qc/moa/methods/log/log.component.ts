import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  result;
  selectedMethod=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getTestMethodsLog();
  }
  view(index){
    this.selectedMethod=this.result[index];
    this.isView=true;
  }
  getTestMethodsLog(){
    this.service.get('qc/method.php?type=getTestMethodsLog').subscribe(response=>{
      this.result=response;
    });
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('pdf1/moa.php?type=testmethod&id='+ this.selectedMethod['id']);
    }else{
      this.service.open('pdf1/moa.php?type=testmethoddigital&id='+ this.selectedMethod['id']);
    }
  }
  download(){
    this.service.open('pdf1/moa.php?type=TestMethodsLog');
  }

}
