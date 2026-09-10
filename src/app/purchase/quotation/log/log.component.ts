import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify;
 

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css', '../../shared/purchase-vapp-host.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  vendors;
  isView = false;
  results;
  selectedResult: [];
  to_date='';
  from_date='';
  vendor_no='';
  isEdit=false;
  isQEdit = false;
  today='';
  item=[];
  vendor_name='';
  material_type='';
  entry_date='';
  gsts: any;
  plant_id:any;

  constructor(private service: DataAccessService,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getQuotationLog();
    this.getVendors();
    this.getGst();
    this.get_rights();
    this.getPendingQuotationsnotification();
  }

  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
 
  getQuotationLog() {
    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=1').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  getQuotationLog1(value){
    console.log(value)

    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=2&category='+value).subscribe(response => {
      this.results = response;
    });
  }
  getQuotationLog3(value){
    this.service.get('purchase/quotation.php?type=getQuotationLog&logic=3&category='+value).subscribe(response => {
      this.results = response;
    });
  }

  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe((response:any) => {
      this.vendors = response;
      console.log("getVendors");
      console.log(response);
    });
  }

  view(result: any) {
    this.selectedResult = result;
    this.isView = true;
  }

  selectedResultUpdate = [];
  selectedResultHis = [];
  editQuatation = false;
  isEditView = false;
  quatationHistory = false;

  update(result: any) {
    this.selectedResultUpdate = result;
    this.isEditView = true;
  }

  closeEdit() {
    this.isEditView = false;
    this.selectedResultUpdate = [];
  }

  history(result: any) {
    this.selectedResultHis = result;
    this.quatationHistory = true;
  }
  
  uploadQuatation(url){
    url = this.service.url + '../../upload/quotation/' + url;
    window.open(url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
 

  /** Export quotation log to Excel with styled header. Uses current filters (Material Type default All + Search). */
  exportToExcel(): void {
    const headers = [
      'Sr.No.', 'Quotation Date', 'Quotation No', 'Vendor Name', 'Materials', 'Status', 'Entry By', 'Entry Date'
    ];
    const filtered = this.filteredMaterials || [];
    const data = filtered.map((row: any, i: number) => {
      const matNames = (row.materials || []).map((m: any) => m.material_name || '').filter(Boolean);
      return [
        i + 1,
        row.vendor_quotation_date ? this.datePipe.transform(row.vendor_quotation_date, 'dd-MM-yyyy') : '',
        row.vendor_quotation_no || '',
        row.vendor_name || '',
        matNames.join(', ') || '',
        row.status === 'approve' ? 'Approved' : (row.status || ''),
        row.entry_by || '',
        row.entry_date ? this.datePipe.transform(row.entry_date, 'dd-MM-yyyy') : ''
      ];
    });

    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Quotation Log', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell) => {
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FF0ea5e9' },
      };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    ws.addRows(data);

    const colCount = headers.length;
    const colWidths: number[] = headers.map((h, c) => (h || '').length + 2);
    if (data.length > 0) {
      for (let c = 0; c < colCount; c++) {
        let maxLen = colWidths[c];
        for (let r = 0; r < data.length; r++) {
          const val = data[r][c];
          const len = val != null ? String(val).length : 0;
          if (len > maxLen) maxLen = Math.min(60, len);
        }
        colWidths[c] = maxLen + 1;
      }
    }
    ws.columns.forEach((col, idx) => {
      if (idx < colCount) {
        col.width = Math.min(55, Math.max(10, colWidths[idx]));
      }
    });

    ws.getRow(1).height = 22;
    if (data.length > 0) {
      const lastRow = data.length + 1;
      for (let r = 2; r <= lastRow; r++) {
        const row = ws.getRow(r);
        row.eachCell((cell) => {
          cell.border = {
            top: { style: 'thin' },
            bottom: { style: 'thin' },
            left: { style: 'thin' },
            right: { style: 'thin' },
          };
          cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        });
        row.height = 18;
      }
    }

    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      const filterSuffix = '';
      a.download = `Quotation_Log${filterSuffix}_${this.datePipe.transform(Date.now(), 'yyyy-MM-dd')}.xlsx`;
      a.click();
      URL.revokeObjectURL(a.href);
      if (typeof alertify !== 'undefined') alertify.success('Excel downloaded successfully');
    });
  }

  editRawMaterial(){

  }
  filterVendor() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
    console.log(material);
      if (material['vendor_no']?.toUpperCase().includes(this.vendor_no?.toUpperCase()) &&       material.materials[0]['material_type']?.toUpperCase().includes(this.material_type.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  getGst(){
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }
  AllRecord(){
    this.vendor_no='';
    this.material_type='';
    this.filterVendor();
 }
 updateQuotation(data){

 }
 editData(){
   this.isEdit = false;
   this.isQEdit = true;
 }
  
 isuser = 'No';
 ischecker = 'No';
 isapprover = 'No';
 qms_approver = 'No';
 dept_head = 'No';
 isauditor = 'No';
 plant_head = 'No';
 shift_allocator = 'No';
 rights;
 loggedInDept;

 get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
   +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
      )
     .subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
       this.isapprover = this.rights[0].isapprover;
       this.qms_approver = this.rights[0].qms_approver;
       this.dept_head = this.rights[0].dept_head;
       this.isauditor = this.rights[0].isauditor;
       this.plant_head = this.rights[0].plant_head;
       this.shift_allocator = this.rights[0].shift_allocator;
     });
 }

 
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results || !Array.isArray(this.results)) return [];
    let list = this.results;

    // Search filter
    if (this.searchQuery && this.searchQuery.trim() !== '') {
      const query = this.searchQuery.toLowerCase().trim();
      list = list.filter((material: any) => {
        return Object.entries(material).some(([key, value]) => {
          if (key === 'entry_date') {
            const dateValue = typeof value === 'string' ? new Date(value) : value;
            return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
          }
          return value != null && value.toString().toLowerCase().includes(query);
        });
      });
    }

    return list;
  }



  unreadQuotations =0;
  
  getPendingQuotationsnotification() {
    this.service.get('purchase/quotation.php?type=getPendingQuotationsForNotification').subscribe(response  => {
      this.unreadQuotations = response['Pending_quatation'];
      if(this.unreadQuotations > 0){
        alertify.error(response['text']);
      }
    });
  }



  save() {
 
    const selectedItems = this.selectedResultUpdate['materials'].filter((term) => term.check);
    if (selectedItems.length === 0) {
      alertify.error('No items selected');
      return;
    }
    console.log(selectedItems);

    const uploadData = new FormData();
    uploadData.append('materials', JSON.stringify(selectedItems));
    
    this.service.post('purchase/quotation.php?type=editQuotationRate', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Quotation updated and sent for approval');
        this.getQuotationLog();
        this.editQuatation = false;
        this.isEditView = false;
        this.selectedResultUpdate = [];
       } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

 
}
