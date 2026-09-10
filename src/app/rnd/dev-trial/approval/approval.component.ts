import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  optimisation='';
  id:any='';
  constructor(private service:DataAccessService) { 

  }

  ngOnInit() {
    this.getPendingDevTrials();
  }
  getPendingDevTrials(){
    this.service.get('rnd/devtrial.php?type=getPendingDevTrials').subscribe(response=>{
     this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  update(status,id:any){
    this.service.get('rnd/devtrial.php?type=updateDevTrial&status='+status+'&id='+id+'&optimisation='+this.optimisation).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success("update successfully");
        this.isView=false;
        this.getPendingDevTrials();
      }else{
        alertify.error('Failed:an error occured')
      }
    });

  }

}
