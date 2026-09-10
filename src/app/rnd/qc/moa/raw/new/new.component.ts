import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];
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

  addmethod(id) {
    this.router.navigate(['/moa/methods/new/' + id]);
  }
}
