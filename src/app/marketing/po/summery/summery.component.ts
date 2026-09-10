import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-summery',
  templateUrl: './summery.component.html',
  styleUrls: ['./summery.component.css'],
  providers: [DatePipe],
})
export class SummeryComponent implements OnInit {
  constructor(private service: DataAccessService) {}

  result: any[] = [];
  isView = false;
  selectedResult: any = [];
  hoveredIndex = -1;
  searchQuery = '';

  // Store colors per PO
  rowColorMap: { [poOrderNo: string]: string } = {};
  colors = ['#9ae2dfff', '#e6f7ff'];

  ngOnInit() {
    this.getPOsLog();
  }

    searchText: string = '';
pendingpoBackup: any[] = [];  
  applyFilter() {
  const query = this.searchText.toLowerCase();

  this.result = this.pendingpoBackup.filter(po =>
    JSON.stringify(po).toLowerCase().includes(query)
  );
}
  getPOsLog() {
    this.service.get('marketing/po.php?type=getPOsLogSummery').subscribe((response: any) => {
      this.result = response.map((po: any) => {
        
        // Calculate batch plan, leftover, and excess
        po.batchPlan = this.calculateBatchPlan(po.planQty, po.batch_formula);
        return po;
      });
         this.pendingpoBackup =this.result;
    });
  }

  // Batch calculation function
  calculateBatchPlan(planQty: number, batchSizes: any[]): any {
    if (!planQty || !batchSizes || batchSizes.length === 0) return { batches: [], leftover: planQty, excess: 0 };

    let remainingQty = planQty;
    const batches: any[] = [];
    let totalProduced = 0;

    // Sort batchSizes descending to use largest batches first
    const sortedBatches = batchSizes.sort((a: any, b: any) => b.batch_formula_weight - a.batch_formula_weight);

    for (let batch of sortedBatches) {
      const size = parseFloat(batch.batch_formula_weight);
      const count = Math.floor(remainingQty / size);
      if (count > 0) {
        batches.push({ size, count });
        totalProduced += count * size;
        remainingQty -= count * size;
      }
    }

    // Check if any leftover remains
    const leftover = remainingQty;
    const smallestBatch = parseFloat(sortedBatches[sortedBatches.length - 1].batch_formula_weight);
    const excess = leftover > 0 ? smallestBatch - leftover : 0;

    return { batches, leftover, excess };
  }

  getRowBgColor(po: any): string {
    const key = po.po_order_no;
    if (!this.rowColorMap[key]) {
      const nextColorIndex = Object.keys(this.rowColorMap).length % this.colors.length;
      this.rowColorMap[key] = this.colors[nextColorIndex];
    }
    return this.rowColorMap[key];
  }

  view(data: any) {
    this.selectedResult = data;
    this.isView = true;
  }

  downloadpo(doc_url: string) {
    const url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(url, '_blank');
  }

  get filteredMaterials(): any[] {
    if (!this.searchQuery?.trim()) return this.result;
    const query = this.searchQuery.toLowerCase().trim();
    return this.result.filter((material) =>
      Object.values(material).some((val: any) => val && val.toString().toLowerCase().includes(query))
    );
  }
}
