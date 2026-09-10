import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-joining',
  templateUrl: './joining.component.html',
  styleUrls: ['./joining.component.css']
})
export class JoiningComponent implements OnInit {
  item;


  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit() {
    
    this.getCandidates();
 

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
    //this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  getCandidates() {
    this.service.get('hr/candidate.php?type=getreportingLog').subscribe(response => {
      this.item = response;
    });
  }
  download(){
    this.service.open('hr/candidate.php?type=downloadJoinreportList');
  }

}
