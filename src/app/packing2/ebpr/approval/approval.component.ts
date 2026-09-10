import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  selectedData;
  selectedProcess=[];
  steps3=[];
  step3data=[];
  stepdata4=[];
  stepdata5=[];
  stepdata6=[];
  stepdata7=[];
  stepdata8=[];
  stepdata9=[];
  stepdata10=[];
  stepdata11=[];
  stepdata12=[];
  stepdata13=[];
  stepdata1=[];
  stepdata2=[];

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingBPRs();
  }

 getPendingBPRs(){
   this.service.get('packing/ebpr.php?type=getPendingBPRs').subscribe(response=>{
     this.results=response;
   });
 }
 view(index){
   this.selectedResult=this.results[index];
   this.selectedData=this.selectedResult['overprinting'];
   this.selectedProcess=this.selectedResult['process'];

   this.steps3=this.selectedProcess[2];
   this.step3data=this.steps3['machinelist'];
   let step4=this.selectedProcess[3];
   this.stepdata4= step4['inspe_details'];
   let step5=this.selectedProcess[4];
   this.stepdata5=step5;
   let step6=this.selectedProcess[5];
   this.stepdata6=step6;
   let step7=this.selectedProcess[6];
   this.stepdata7=step7;
   console.log(this.stepdata4);
   let step8=this.selectedProcess[7];
   this.stepdata8=step8;
   let step9=this.selectedProcess[8];
   this.stepdata9=step9;
   let step10=this.selectedProcess[9];
   this.stepdata10=step10;
    let step11=this.selectedProcess[10];
    this.stepdata11=step11;
    let step12=this.selectedProcess[11];
    this.stepdata12=step12;
    let step13=this.selectedProcess[12];
    this.stepdata13=step13;

    let step1=this.selectedProcess[0];
   this.stepdata1=step1;
   let step2=this.selectedProcess[1];
   this.stepdata2=step2;
   this.isView=true;
 }

 update(status) {
  this.service.get('packing/ebpr.php?type=updateBPR&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
    if (response['status']) {
      alert('data Updated Successfully');
      this.isView = false;
      this.getPendingBPRs();
    } else {
      alert('Failed: An error occured, please try again!');
    }
  });
}

}
