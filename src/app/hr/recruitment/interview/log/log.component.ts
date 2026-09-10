import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  selectedReport=[];
  isView=false;
  department_name;
  departments;
  designations;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getInterviewsLog();
    this. getDepartments();
    this. getDesignation()
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
  // ---------------------------------------------------------------------//
  getInterviewsLog(){
    this.service.get('hr/interview.php?type=getInterviewerLog').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }
  getDesignation() {
    this.service.get('common.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  download(){
    this.service.open('hr/candidate.php?type=downloadCandidatesList');
  }
}