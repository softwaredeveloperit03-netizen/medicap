import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  
  results:any = [];
  filteredResults:any = [];
  loading = false;
  searchText = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getBulkStockList();
  }

  getBulkStockList() {
    this.loading = true;
    this.service.get('production/bulk_stock.php?type=getBulkStockList').subscribe(response => {
      this.results = response || [];
      this.filteredResults = this.results;
      this.loading = false;
    }, error => {
      this.loading = false;
      console.error('Error fetching bulk stock list:', error);
    });
  }

  filterTable() {
    if (!this.searchText) {
      this.filteredResults = this.results;
      return;
    }
    const search = this.searchText.toLowerCase();
    this.filteredResults = this.results.filter((item: any) => {
      return (
        (item.bulkname && item.bulkname.toLowerCase().includes(search)) ||
        (item.bulkcode && item.bulkcode.toLowerCase().includes(search)) ||
        (item.arno && item.arno.toLowerCase().includes(search))
      );
    });
  }

  viewUses(id: number) {
    this.router.navigate(['/fproduction/bulk_stock/view', id]);
  }

  calculateBalance(item: any): number {
    const mfgQty = parseFloat(item.mfg_qty) || 0;
    const usedQty = parseFloat(item.used_qty) || 0;
    return mfgQty - usedQty;
  }

}

