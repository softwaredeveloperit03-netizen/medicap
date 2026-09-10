import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  isTestView = false;
  specifications;
  // isTestView=false;

  selectedSpec = [];
  selectedTest = [];
  spectest;
  isViewTest: boolean;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPendingRawMOA();
  }
  
  getPendingRawMOA() {
    this.service.get('qc/method.php?type=getPendingRawMOA').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }
  viewTests(index){
    this.getPendingRawMOA();
    this.isTestView = true;
    this.selectedTest = this.specifications[index];
  }

  // addmethod(id) {
  //   this.router.navigate(['/qc/moa/methods/news/' + id]);
  // }
}