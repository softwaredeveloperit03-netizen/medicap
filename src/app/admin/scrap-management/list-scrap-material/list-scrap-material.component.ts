import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-list-scrap-material',
  templateUrl: './list-scrap-material.component.html',
  styleUrls: ['./list-scrap-material.component.css']
})
export class ListScrapMaterialComponent implements OnInit {
  reports;
  selectedReview = [];
  isView = false;
  grades;
  grade='';
  item=[];
  results='';
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
   this.getChemiclLog();
   this.service.observableGrade.subscribe(response => {
    this.grades = response;

    this.get_rights();
  });
  }
  
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

  getChemiclLog() {
    this.service.get('qc/chemical.php?type=getChemicalsLog').subscribe(response => {
      this.reports = response;
      this.filterItem();
    });
  }

  download() {
    this.service.open('qc/chemical.php?type=downloadChemicalsLog&grade='+this.grade)
  }

  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.reports.length; i++) {
      let material = this.reports[i];
      if (material['grade'].toUpperCase().includes(this.grade.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }

  AllRecord(){
    this.item =this.reports;
    this.grade='';

}
}
 
