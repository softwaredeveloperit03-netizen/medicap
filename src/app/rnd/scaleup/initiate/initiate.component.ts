import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-initiate',
  templateUrl: './initiate.component.html',
  styleUrls: ['./initiate.component.css']
})
export class InitiateComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getDevTrials();
   
  }
  getDevTrials(){
    this.service.get('rnd/optimisation.php?type=getDevTrials').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true; 
  }
  proceed(){
    this.service.post("rnd/optimisation.php?type=saveInitialOptimisation",JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.successfully("send successfully");
        this.isView=false;
        this.getDevTrials();
      }else{
        alertify.error("error occured");
      }

    });

  }

}
