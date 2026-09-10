import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common'; 
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe],
})
export class ApprovalComponent implements OnInit {
  isView = false;
  results;

  selectedResult = [];
  units;
  vendors;
  isChange = false;
  vendor_unit = '';
  material_type = '';
  from_date = '';
  to_date = '';
  today = '';
  selectedLocation = [];

  remark = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getPendingChallans();
    this.getVendors();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10;
  }

  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }

  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf() {
    this.isView = false;
    //  this.getLogs();
    this.currentPage = 1;
    this.pageSize = 10;
  }
  // ---------------------------------------------------------------------//
  download() {
    this.service.open('store/challan.php?type=DownloadInwordPDF');
  }
  getPendingChallans() {
    this.service
      .get(
        'store/challan.php?type=getApproveChallans&material_type=' +
          this.material_type +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  searchQuery;
  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
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

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    // this.getVendorUnits();
  }

  selectLocation(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLocation = this.units[index];
    }
  }

  // getVendorUnits() {
  //   this.service.get('purchase/vendor.php?type=getVendorUnit&vendor_no=' + this.selectedResult['vendor_no']).subscribe(response => {
  //     this.units = response;
  //   });
  // }

  updateChallan(status) {
    this.selectedResult['remark'] = this.remark;
    this.service
      .post(
        'store/challan.php?type=approve_challan&status=' +
          status +
          '&id=' +
          this.selectedResult['id'] +
          '&po_no=' +
          this.selectedResult['po_no'] +
          '&vendor_unit=' +
          this.vendor_unit,
        JSON.stringify(this.selectedResult)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.remark = '';
          alertify.success('Record updated successfully');
          this.isView = false;
          this.getPendingChallans();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  calculation(index) {
    let materials = this.selectedResult['materials'];
    let selectedMaterial = materials[index];
    selectedMaterial['gross_total'] =
      +selectedMaterial['qty'] * +selectedMaterial['rate'];
    selectedMaterial['gst_total'] =
      (+selectedMaterial['gross_total'] * +selectedMaterial['gst']) / 100;
    selectedMaterial['net_total'] =
      +selectedMaterial['gross_total'] + +selectedMaterial['gst_total'];

    selectedMaterial['gross_total'] = +parseFloat(
      selectedMaterial['gross_total']
    ).toFixed(2);
    selectedMaterial['gst_total'] = +parseFloat(
      selectedMaterial['gst_total']
    ).toFixed(2);
    selectedMaterial['net_total'] = +parseFloat(
      selectedMaterial['net_total']
    ).toFixed(2);

    selectedMaterial['diff'] = +parseFloat(
      +selectedMaterial['received_rate'] - +selectedMaterial['rate'] + ''
    ).toFixed(2);

    selectedMaterial['diff_amt'] =
      +selectedMaterial['diff'] * +selectedMaterial['qty'];

    materials[index] = selectedMaterial;
    this.selectedResult['materials'] = materials;

    let gross_total = 0;
    let gst_total = 0;
    let net_total = 0;
    let diff_total = 0;
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      gross_total += +material['gross_total'];
      gst_total += +material['gst_total'];
      net_total += +material['net_total'];
      diff_total += +material['diff_amt'];
    }
    this.selectedResult['gross_total'] = gross_total;
    this.selectedResult['gst_total'] = gst_total;
    this.selectedResult['net_total'] = net_total;
    this.selectedResult['diff_total'] = diff_total;
  }

  viewChallan(url) {
    url = this.service.url + '../../upload/challan/' + url;
    window.open(url, '_blank');
  }

  AllRecord() {
    this.service
      .get('store/challan.php?type=getAllApproveChallans')
      .subscribe((response: any) => {
        this.results = response;
      });
    this.material_type = '';
    this.from_date = '';
    this.to_date = '';
  }
}
