import { Component, OnInit } from '@angular/core';

declare let alertify: any;

interface MrpLogEntry {
  seq: number;
  productName: string;
  actionType: 'New MRP' | 'MRP Change';
  revision: number;
  previousMrp: string;
  currentMrp: string;
  reasonForChange: string;
  changedBy: string;
  changedOn: string;
}

@Component({
  selector: 'app-mrp-history',
  templateUrl: './mrp-history.component.html',
  styleUrls: ['./mrp-history.component.css']
})
export class MrpHistoryComponent implements OnInit {
  activeTab: 'new' | 'change' = 'new';
  productOptions: string[] = ['Aurapara', 'Aracet 10 mg', 'Auracip 100 mg', 'Aurenext 250mg'];
  logEntries: MrpLogEntry[] = [];

  newMrpForm = {
    productName: '',
    mrpCurrency: ''
  };

  changeMrpForm = {
    productName: '',
    mrpCurrency: '',
    changedMrpCurrency: '',
    reasonForChange: ''
  };

  constructor() { }

  ngOnInit(): void {
    this.seedDemoHistory();
  }

  setTab(tab: 'new' | 'change'): void {
    this.activeTab = tab;
  }

  addNewMrp(): void {
    if (!this.newMrpForm.productName || !this.newMrpForm.mrpCurrency) {
      alertify.error('Please select Product Name and enter MRP Currency');
      return;
    }

    const previous = this.getLatestMrp(this.newMrpForm.productName);
    const nextRevision = this.getNextRevision(this.newMrpForm.productName);
    this.logEntries.unshift({
      seq: 0,
      productName: this.newMrpForm.productName,
      actionType: 'New MRP',
      revision: nextRevision,
      previousMrp: previous,
      currentMrp: this.newMrpForm.mrpCurrency,
      reasonForChange: '-',
      changedBy: localStorage.getItem('username') || 'Demo User',
      changedOn: new Date().toISOString()
    });
    this.rebuildSequence();
    this.newMrpForm = { productName: '', mrpCurrency: '' };
    alertify.success('New MRP added to log');
  }

  onChangeProductSelection(): void {
    this.changeMrpForm.mrpCurrency = this.getLatestMrp(this.changeMrpForm.productName);
  }

  addMrpChange(): void {
    if (!this.changeMrpForm.productName || !this.changeMrpForm.changedMrpCurrency || !this.changeMrpForm.reasonForChange) {
      alertify.error('Please fill Product Name, Changed MRP Currency and Reason for Change');
      return;
    }

    const previous = this.changeMrpForm.mrpCurrency || this.getLatestMrp(this.changeMrpForm.productName);
    const nextRevision = this.getNextRevision(this.changeMrpForm.productName);
    this.logEntries.unshift({
      seq: 0,
      productName: this.changeMrpForm.productName,
      actionType: 'MRP Change',
      revision: nextRevision,
      previousMrp: previous || '-',
      currentMrp: this.changeMrpForm.changedMrpCurrency,
      reasonForChange: this.changeMrpForm.reasonForChange,
      changedBy: localStorage.getItem('username') || 'Demo User',
      changedOn: new Date().toISOString()
    });
    this.rebuildSequence();
    this.changeMrpForm = { productName: '', mrpCurrency: '', changedMrpCurrency: '', reasonForChange: '' };
    alertify.success('MRP change added to revision history');
  }

  private getLatestMrp(productName: string): string {
    const latest = this.logEntries.find((x) => x.productName === productName);
    return latest ? latest.currentMrp : '';
  }

  private getNextRevision(productName: string): number {
    return this.logEntries.filter((x) => x.productName === productName).length + 1;
  }

  private rebuildSequence(): void {
    this.logEntries = this.logEntries.map((entry, index) => ({ ...entry, seq: index + 1 }));
  }

  private seedDemoHistory(): void {
    this.logEntries = [
      {
        seq: 1,
        productName: 'Aurenext 250mg',
        actionType: 'MRP Change',
        revision: 2,
        previousMrp: 'Rs. 120',
        currentMrp: 'Rs. 125',
        reasonForChange: 'Updated pricing due to packaging cost revision',
        changedBy: 'Management Admin',
        changedOn: '2026-03-25T09:45:00'
      },
      {
        seq: 2,
        productName: 'Aurenext 250mg',
        actionType: 'New MRP',
        revision: 1,
        previousMrp: '-',
        currentMrp: 'Rs. 120',
        reasonForChange: '-',
        changedBy: 'Management Admin',
        changedOn: '2026-03-20T11:00:00'
      },
      {
        seq: 3,
        productName: 'Auracip 100 mg',
        actionType: 'MRP Change',
        revision: 2,
        previousMrp: 'Rs. 85',
        currentMrp: 'Rs. 89',
        reasonForChange: 'Updated pricing after RM cost increase',
        changedBy: 'Management Admin',
        changedOn: '2026-03-24T16:20:00'
      },
      {
        seq: 4,
        productName: 'Auracip 100 mg',
        actionType: 'New MRP',
        revision: 1,
        previousMrp: '-',
        currentMrp: 'Rs. 85',
        reasonForChange: '-',
        changedBy: 'Management Admin',
        changedOn: '2026-03-18T13:10:00'
      },
      {
        seq: 5,
        productName: 'Aracet 10 mg',
        actionType: 'New MRP',
        revision: 1,
        previousMrp: '-',
        currentMrp: 'Rs. 62',
        reasonForChange: '-',
        changedBy: 'Management Admin',
        changedOn: '2026-03-17T10:30:00'
      },
      {
        seq: 6,
        productName: 'Aurapara',
        actionType: 'MRP Change',
        revision: 2,
        previousMrp: 'Rs. 45',
        currentMrp: 'Rs. 48',
        reasonForChange: 'MRP revised post market review',
        changedBy: 'Management Admin',
        changedOn: '2026-03-23T12:00:00'
      },
      {
        seq: 7,
        productName: 'Aurapara',
        actionType: 'New MRP',
        revision: 1,
        previousMrp: '-',
        currentMrp: 'Rs. 45',
        reasonForChange: '-',
        changedBy: 'Management Admin',
        changedOn: '2026-03-15T09:00:00'
      }
    ];
  }

}