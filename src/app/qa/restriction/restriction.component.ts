import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-restriction',
  templateUrl: './restriction.component.html',
  styleUrls: ['./restriction.component.css']
})
export class RestrictionComponent implements OnInit {

  departments: any[] = [];
  results: any[] = [];
  isView = false;
  selectedResult: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getProductsLog();
  }

  getProductsLog() {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    if (this.results && index >= 0 && index < this.results.length) {
      this.selectedResult = { ...this.results[index] };
      this.isView = true;
    }
  }
}
