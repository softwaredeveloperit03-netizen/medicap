import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  phisicians;
  isView = false;
  results;
  selectedResult = [];
  doctor_name ='';
  clinic_name;
  data = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPhysiciansLog();
    this.getApprovedPhisicians();
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
  getApprovedPhisicians() {
    this.service.get('hr/physician.php?type=getPhysicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }

  getPhysiciansLog() {
    this.service.get('hr/physician.php?type=getPhysiciansLog').subscribe(response => {
      this.results = response;
      this.filterData();
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  
  filterData() {
    this.data = [];
    for (let i = 0; i < this.results.length; i++) {
      let doctor = this.results[i];
      if (doctor['doctor_name'].toUpperCase().includes(this.doctor_name.toUpperCase())) {
        this.data[this.data.length] = doctor;
      }
    }
  }
  
}

