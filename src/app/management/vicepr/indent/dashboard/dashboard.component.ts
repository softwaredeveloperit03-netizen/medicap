import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

 
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
 
    this.getPendingIndends();
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
  
  results

  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getIndentByplantHead').subscribe((response: any) => {
      this.results = response;
    });
  }

  isView = false;
  materials=[];
  selectedResult=[];

   view(index) {
    this.selectedResult = this.results[index];
    this.materials = this.selectedResult['materials'];
    this.isView = true;
  }

  

}
