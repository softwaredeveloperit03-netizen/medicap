import { AfterViewInit, Component, OnDestroy } from '@angular/core';
import { Chart, registerables } from 'chart.js';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements AfterViewInit, OnDestroy {
  rawMaterialmonthly: Chart | null = null;
  month: string[] = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  Raw_Material_Monthly_Purchase_Data: number[] = [420100, 223121, 311122, 434221, 553121, 624511, 662516, 321133, 262134, 443621, 203121, 301122];
  Packing_Material_Monthly_Purchase_Data: number[] = [220100, 423121, 511122, 134221, 253121, 524511, 662516, 421133, 162134, 343621, 603121, 501122];
  Finished_Material_Monthly_Purchase_Data: number[] = [320100, 123121, 211122, 334221, 453121, 324511, 562516, 221133, 362134, 543621, 103121, 201122];

  cards: QcDeptCard[] = [
    { id: 'raw', title: 'Raw Material Disp', route: 'raw', icon: 'fa-box-open', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material Disp', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'solvent', title: 'Solvent Dispensing', route: 'solvent', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  constructor() {
    Chart.register(...registerables);
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.MaterialMonthlypurchase(), 0);
  }

  ngOnDestroy(): void {
    if (this.rawMaterialmonthly) {
      this.rawMaterialmonthly.destroy();
      this.rawMaterialmonthly = null;
    }
  }

  private MaterialMonthlypurchase(): void {
    const canvas = document.getElementById('MaterialWiseMonthlyChart') as HTMLCanvasElement;
    if (!canvas || this.rawMaterialmonthly) {
      return;
    }
    this.rawMaterialmonthly = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: this.month,
        datasets: [
          { label: 'Raw', data: this.Raw_Material_Monthly_Purchase_Data, backgroundColor: '#0f766e', borderColor: '#0d9488', borderWidth: 2 },
          { label: 'Packing', data: this.Packing_Material_Monthly_Purchase_Data, backgroundColor: '#6366f1', borderColor: '#8b5cf6', borderWidth: 2 },
          { label: 'Finished', data: this.Finished_Material_Monthly_Purchase_Data, backgroundColor: '#ec4899', borderColor: '#f43f5e', borderWidth: 2 },
        ],
      },
    });
  }
}
