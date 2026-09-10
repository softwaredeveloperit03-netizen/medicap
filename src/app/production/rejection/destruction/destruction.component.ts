import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-destruction',
  templateUrl: './destruction.component.html'
})
export class DestructionComponent implements OnInit {
  nofound = true;
  dataForm;
  step ='';
  isForm = false;
  constructor(private router:Router, private service:DataAccessService) { }

  ngOnInit(): void {
    this.getonlinereport();
  }
  closeform(){
    this.stepslist =[];
    this.isForm = false;
  }
  datalist = [];
  getonlinereport(){
    this.service.get('rejection.php?type=getdestruction').subscribe((response:any) => {
      this.datalist = response;
      this.nofound = false;
    })
  }
  destroyrejection(value){
    if(value.valid){
      alertify.success('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alertify.error('All feild Are Required');
    }
  }
  selecteddata;
  viewform(index){
    this.selecteddata = this.datalist[index];
    this.isForm = true;
  }
  stepslist =[];
  stepindex = 0;
  addsteps() {
    let temptermList = {};
    temptermList['steps'] = this.step;
    this.stepslist[this.stepindex] = temptermList;
    this.stepindex++;
    this.step= '';
  } 

}
