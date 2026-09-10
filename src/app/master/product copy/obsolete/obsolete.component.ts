import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-product-obsolete',
  templateUrl: './obsolete.component.html',
  styleUrls: ['./obsolete.component.css']
})
export class ProductObsoleteComponent implements OnInit {
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getProductsLog();
  }

  results;
  loading = false;
  getProductsLog() {
    this.loading = true;
    this.service.get('master/product.php?type=getProductsByStatus&status=Absolute').subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      }
    });
  }

  selectedProduct: any = {};
  isView = false;
  view(result: any) {
    this.selectedProduct = result;
    this.isView = true;
  }

  getDisplayVal(val: any): string | number | any {
    if (val === undefined || val === null) return 'NA';
    if (typeof val === 'string' && val.trim() === '') return 'NA';
    return val;
  }

  formatEntryDate(val: any): string {
    if (val == null || val === '') return 'NA';
    const d = typeof val === 'string' ? new Date(val) : val;
    if (isNaN(d.getTime())) return 'NA';
    return d.toLocaleDateString();
  }

  getEntryByDisplay(result: any): string {
    if (!result) return 'NA';
    const name = result.entry_by_name || result.created_by_name || result.entry_by || result.created_by || '';
    const id = result.entry_by_id || result.created_by_id || '';
    if (name && id) return name + ' (' + id + ')';
    if (name) return name;
    if (id) return 'ID: ' + id;
    return 'NA';
  }

  searchQuery;
  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((material) =>
      Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        }
        return value && value.toString().toLowerCase().includes(query);
      })
    );
  }
}
