import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
} from 'src/app/marketing/clients/client-service.helper';
import * as ExcelJS from 'exceljs';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {


  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.get_rights();
  }


  dept_head = 'No';
  rights;

  get_rights() {
   this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department') ).subscribe((response) => {
       this.rights = response;
       this.dept_head = this.rights[0].dept_head;
        this.getLeadsLog(this.dept_head);
    });
  }
 
  getLeadsLog(leadFor) {
    this.service.get('marketing/lead.php?type=getLeadsLog&leadFor='+leadFor).subscribe(response => {
      this.leadResults = response;
    });
  }
 

  viewDoc(url) {
    url = this.service.url + '../../upload/leads/' + url;
    window.open(url, '_blank');
  }

  isView = false;

  selectedEnquiry = [];
  savedServiceEntries: SavedServiceEntry[] = [];

  view(data) {
    this.selectedEnquiry = { ...data };
    this.savedServiceEntries = parseClientServiceEntries(this.selectedEnquiry);
    this.isView = true;
  }

  getDescriptionLines(entry: SavedServiceEntry): string[] {
    return getDescriptionLines(entry.descriptions);
  }

  getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
    return getSavedEntryProductsLabel(entry);
  }
 
  selectedProduct = [];
  isProductView = false;
  viewProduct(data){
    this.selectedProduct = data;
    this.isProductView = true;
  }


   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.leadResults; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.leadResults.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  /** Download lead log (filtered) as formatted Excel */
  downloadLeadLog() {
    const list = this.filteredMaterials;
    if (!list || list.length === 0) {
      alertify.warning('No data to download.');
      return;
    }
    const formatDate = (d: any) => {
      if (!d) return '-';
      const dt = new Date(d);
      return isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
    };
    const formatAcknow = (lead: any) => {
      if (lead.acknowledgeStatus === 'Pending') return 'PENDING';
      return (lead.acknowledgeBy || '-') + ' / ' + formatDate(lead.acknowledgeOn);
    };
    const formatClientAction = (lead: any) => {
      return lead.source_table === 'NEW' ? (lead.clientStatus || '').toUpperCase() : 'Existing Client';
    };
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Complete Lead Log', { pageSetup: { orientation: 'landscape' } });
    const headers = ['Sr.No.', 'Enquiry No.', 'Client Name', 'Enquiry Generated Through', 'Reference By', 'Status', 'Acknow. Status', 'Entry By', 'Entry On', 'Client Action'];
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0e4370' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;
    list.forEach((lead: any, i: number) => {
      ws.addRow([
        i + 1,
        lead.enquiry_no || '-',
        lead.LglNm || '-',
        lead.enqGeneratedThrough || '-',
        lead.reference || '-',
        (lead.status || '-').toUpperCase(),
        formatAcknow(lead),
        lead.entryBy || '-',
        formatDate(lead.entryOn),
        formatClientAction(lead)
      ]);
    });
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= list.length + 1; r++) {
      const row = ws.getRow(r);
      row.height = 20;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }
    const colWidths = [8, 14, 18, 18, 14, 10, 24, 12, 14, 16];
    ws.columns.forEach((col, idx) => { col.width = colWidths[idx] ?? 14; });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `complete-lead-log-${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    });
  }
}
