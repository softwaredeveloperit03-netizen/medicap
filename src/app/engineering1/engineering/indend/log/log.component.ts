import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
 
     
  results;
  to_date='';
  from_date='';
  material=[];

  selectedResult=[];
  isView=false;
  isQuatation=false;
  types;
  materials;
  item=[];
  department_name='';
  today='';
  request_no='';
  isEdit: any;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getDeptIndendsLog();
  
    
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }


  getDeptIndendsLog() {
    this.service.get('purchase/indent.php?type=getindendLogBydept&from_department=Engineering').subscribe((response : any) => {
   this.results = response; 
  });
}
download(){
this.service.open('purchase/indent.php?type=downloadindendLogBydept')
}
 
 

  view(index){
    this.selectedResult = this.results[index];
    this.material=this.selectedResult['materials'];
    this.isView = true;
    this.request_no='';
  }

 
}
