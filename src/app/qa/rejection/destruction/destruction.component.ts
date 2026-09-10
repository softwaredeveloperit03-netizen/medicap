import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-destruction',
  templateUrl: './destruction.component.html'
})
export class DestructionComponent implements OnInit {
  isForm = false;
  constructor(private router:Router, private service:DataAccessService) { }

  ngOnInit(): void {
    this.getonlinereport();
  }
  closeform(){
    this.router.navigateByUrl('rejection/dashboard')
  }
  datalist = [];
  getonlinereport(){
    this.service.get('rejection.php?type=getdestruction').subscribe((response:any) => {
      this.datalist = response;
    })
  }
  destroyrejection(value){
    if(value.valid){
      alert('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alert('All feild Are Required');
    }
  }
  selectedreport = [];
  viewreport(index){
    this.selectedreport = this.datalist[index];
    this.isForm = true;
  }

}
