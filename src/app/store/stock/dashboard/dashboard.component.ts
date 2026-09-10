import { AfterViewInit, ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { Chart, registerables } from 'chart.js';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit, AfterViewInit, OnDestroy {
  rawMaterialmonthly: Chart | null = null;
  hasChart = false;
  MaterialName: string[] = [];
  MaterialQty: number[] = [];
  MatColor: string[] = [];

  private colorArray: string[] = [
    '#0f766e', '#6366f1', '#ec4899', '#14b8a6', '#8b5cf6',
    '#f43f5e', '#06b6d4', '#eab308', '#22c55e', '#f97316',
  ];

  cards: QcDeptCard[] = [
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-box-open', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {
    Chart.register(...registerables);
  }

  ngOnInit(): void {
    this.getMaterialOutDetails();
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.buildChartIfReady(), 100);
  }

  ngOnDestroy(): void {
    if (this.rawMaterialmonthly) {
      this.rawMaterialmonthly.destroy();
      this.rawMaterialmonthly = null;
    }
  }

  getMaterialOutDetails(): void {
    this.service.get('store/bincard.php?type=getMaterials&material_type=Raw Material').subscribe({
      next: (response: any) => {
        const all = Array.isArray(response) ? response : [];
        this.MaterialName = [];
        this.MaterialQty = [];
        this.MatColor = [];
        all.forEach((item: any, i: number) => {
          this.MaterialName.push(item?.material_name ?? '');
          this.MaterialQty.push(Number(item?.received_qty) || 0);
          this.MatColor.push(this.colorArray[i % this.colorArray.length]);
        });
        this.hasChart = this.MaterialName.length > 0;
        this.cdr.detectChanges();
        setTimeout(() => this.buildChartIfReady(), 50);
      },
      error: () => this.cdr.detectChanges()
    });
  }

  private buildChartIfReady(): void {
    const canvas = document.getElementById('MaterialWiseMonthlyChart') as HTMLCanvasElement;
    if (!canvas || this.rawMaterialmonthly || this.MaterialName.length === 0) {
      return;
    }
    this.rawMaterialmonthly = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: this.MaterialName,
        datasets: [{
          label: 'Material',
          data: this.MaterialQty,
          backgroundColor: this.MatColor,
          borderColor: '#0f172a',
          borderWidth: 2,
        }],
      },
    });
    this.cdr.detectChanges();
  }
}
