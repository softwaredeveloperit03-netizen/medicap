import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-expired-materials',
  templateUrl: './expired-materials.component.html',
})
export class ExpiredMaterialsComponent implements OnInit {
  results: any[] = [];
  loading = false;
  materialType = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadExpired();
  }

  loadExpired() {
    this.loading = true;
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.loadList('store/raw.php?type=getExpiredMaterials' + q).subscribe({
      next: (response: any[]) => {
        this.results = response || [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }
}
