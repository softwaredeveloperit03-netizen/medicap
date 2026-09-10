import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css'],
})
export class CalenderComponent implements OnInit {
  results: any[] = [];
  allResults: any[] = [];
  loading = false;
  material_code = '';
  material_name = '';
  category = '';
  grn_no = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadCalendar();
  }

  loadCalendar() {
    this.loading = true;
    const q = '';
    this.service.getJsonArray('qc/retest.php?type=getRetestCalendarDueWindow' + q).subscribe({
      next: (response: any[]) => {
        this.allResults = response || [];
        this.applyFilters();
        this.loading = false;
      },
      error: () => {
        this.allResults = [];
        this.results = [];
        this.loading = false;
      },
    });
  }

  download() {
    this.service.open('store/raw.php?type=downloadRetestCalendar');
  }

  applyFilters() {
    this.results = (this.allResults || []).filter((row) => {
      const code = (row.material_code || '').toString().toUpperCase();
      const name = (row.material_name || '').toString().toUpperCase();
      const subtype = (row.material_subtype || '').toString().toUpperCase();
      const grn = (row.grn_no || '').toString().toUpperCase();
      return (
        (!this.material_code || code.includes(this.material_code.toUpperCase())) &&
        (!this.material_name || name.includes(this.material_name.toUpperCase())) &&
        (!this.category || subtype.includes(this.category.toUpperCase())) &&
        (!this.grn_no || grn.includes(this.grn_no.toUpperCase()))
      );
    });
  }
}
