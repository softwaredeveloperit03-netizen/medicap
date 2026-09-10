import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  clients;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getReceivedMaterials();
  }

  getReceivedMaterials(){
    this.service.get('packing/plan.php?type=getPendingPlans').subscribe(response=>{
      this.results = response;
    });
  }
  
  view(index){
    this.selectedResult=this.results[index];
    this.getClients();
    this.isView=true;
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients=response;
    })
  }

  save(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service.post('packing/plan.php?type=savePlan',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        this.isView = false;
        this.getReceivedMaterials();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });

  }

}
