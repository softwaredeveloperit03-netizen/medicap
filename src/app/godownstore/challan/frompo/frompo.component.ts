import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-frompo',
  templateUrl: './frompo.component.html',
  styleUrls: ['./frompo.component.css'],
})
export class FrompoComponent implements OnInit {
  isView = false;
  results;
  weighing_procedure;
  selectedFile: File;
  isUpload = 0;
  selectedResult = [];
  units;
  isChange = false;
  vendor_unit = '';
  eway_bill_no = '';

  selectedLocation = [];

  remark = '';
  invoice_no;
  invoice_date;
  invoice_amt;
  tax_amt;
  net_amt;
  plant_id: any;
  e_way = 'No';
  uploadedFileNames;

  constructor(
    private service: DataAccessService,private router:Router,
    private cdr: ChangeDetectorRef
  ) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit(): void {
    this.getPendingChallans();
    this.getClientList();
  }





  getClass(data){

    if(data['isOpenPo'] == 'OPEN'){
      return 'open';
    }else{
      return 'Jadugar';
    }


  }




  calculateNetAmount(index) {
    const parsedInvoiceAmt = parseFloat(this.invoice_data[index]?.invoice_amt);
    const parsedTaxAmt = parseFloat(this.invoice_data[index]?.tax_amt);

    if (!isNaN(parsedInvoiceAmt) && !isNaN(parsedTaxAmt)) {
      this.invoice_data[index].net_amt = (
        parsedInvoiceAmt + parsedTaxAmt
      ).toFixed(2); // Keeping two decimal places if needed
    } else {
      this.invoice_data[index].net_amt = '0.00'; // Ensuring it's a string for consistency
    }
  }

  getPendingChallans() {
    this.service
      .get('store/challan.php?type=getPendingChallans')
      .subscribe((response) => {
        this.results = response;
      });
  }

  clients;

  getUploadChallans() {
    this.service.get('store/challan.php?type=getChallans&ch_no=' +this.selectedResult['challan_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }
  getClientList() {
    this.service.get('store/challan.php?type=getClientList').subscribe((response) => {
        this.clients = response;
    });
  }



  invoice_data = [];
  invoice_data1 = [];
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    // this.getVendorUnits();
    this.invoice_data = this.selectedResult['invoice_data'];
    this.invoice_data1 = this.selectedResult['invoice_data'];
    this.cdr.detectChanges();

    if (this.selectedResult['material_type'] == 'Raw Material') {
      this.weighing_procedure = 'Inhouse-Weighing';
    } else {
      this.weighing_procedure = 'Counting';
    }

    this.getUploadChallans();
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

  materialFor = 'Own';
  materialForName = 'Own';

  updateChallan(data, status) {
    if (!data.valid) {
      alertify.error('Please Select Challan');
      return;
    }

    const selectedItems = this.selectedResult['materials'].filter(
      (term) => term.selected
    );
    const notSelectedCount = this.selectedResult['materials'].filter(
      (term) => !term.selected
    ).length;

    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    console.log(selectedItems);
    console.log('notSelectedCount' + notSelectedCount);

    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append(
        'challan_file',
        this.selectedFile,
        this.selectedFile.name
      );
    }

    uploadData.append('materialFor', this.materialFor);
    uploadData.append('materialForName', this.materialForName);
    uploadData.append('materials', JSON.stringify(selectedItems));
    uploadData.append('invoice_data', JSON.stringify(this.invoice_data));

    this.service
      .post(
        'store/challan.php?type=CycloneUpdateChallan&status=' +
          status +
          '&id=' +
          this.selectedResult['id'] +
          '&weighing_procedure=' +
          this.weighing_procedure +
          '&notSelectedCount=' +
          notSelectedCount +
          '&po_no=' +
          this.selectedResult['po_no'] +
          '&challan_no=' +
          this.selectedResult['challan_no'] +
          '&ch_no=' +
          this.selectedResult['ch_no'],
        uploadData
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.remark = '';
          this.materialFor = 'Own';
          this.materialForName = 'Own';
          alertify.success('Record updated successfully');
          //this.isView = false;
          //this.getPendingChallans();
          window.location.reload();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  calculation(index) {
    let materials = this.selectedResult['materials'];
    let selectedMaterial = materials[index];

    selectedMaterial['diff'] = (
      parseFloat(selectedMaterial['received_rate']) -
      parseFloat(selectedMaterial['rate'])
    ).toFixed(2);

    selectedMaterial['diff_amt'] =
      +selectedMaterial['diff'] * +selectedMaterial['qty'];

    materials[index] = selectedMaterial;
    this.selectedResult['materials'] = materials;
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
  }

  take(index) {
    const arr = this.selectedResult['invoice_data'][index];
  }

  handleSelectionChange(data: string[], i: number) {
    // Ensure `materials` exists and `i` is valid
    if (
      !this.selectedResult['materials'] ||
      !this.selectedResult['materials'][i]
    ) {
      console.error('Invalid index or materials array is not defined');
      return;
    }

    // Join the selected values with a comma
    this.selectedResult['materials'][i].tax_invoice = data.join(', ');
    console.log(this.selectedResult['materials'][i].tax_invoice);
  }
  close() {
    this.isView = false;
    this.router.navigate(['/store/challan/frompo']);
    window.location.reload();
  }
  // ============================================================ //

  selectedItems: any[] = []; // Assuming selectedItems is an array holding relevant material data

  file: File;



  onFileSelected(event) {
    if (event.target.files.length === 1) {
      // Store the selected file in the userForm variable
      this.file = event.target.files[0];
    }
  }



  challanFIle: File;
 
  onFileChangedpsb(event) {
    this.challanFIle = event.target.files[0];
  }


  
  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
   window.open(url, '_blank');
 }



  uploadFile(data) {
   
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let formData = new FormData();
  
    if (this.challanFIle !== undefined) {
      formData.append('challanFIle', this.challanFIle, this.challanFIle.name);
    } 
 
    formData.append('ch_no', this.selectedResult['challan_no']);
    formData.append('selectedTaxInvoice', this.selectedTaxInvoice);
 

    this.service.post('store/challan.php?type=uploadChallan', formData ).subscribe((response) => {
        if (response['status'] == 'success') {
          data.reset();
          this.getUploadChallans();
          alertify.success('Data Saved Successfully!');
        } else {
          alertify.error('An error occured, please try again!');
        }
      });
  }


 


  selectedTaxInvoice: string | null = null;

  onTaxInvoiceChange(): void {
    // Find the index of the selected invoice
    const index = this.invoice_data1.findIndex(
      (invoice) => invoice.tax_invoice === this.selectedTaxInvoice
    );

    // Remove the selected invoice from the array
    if (index !== -1) {
      this.invoice_data1.splice(index, 1);

      // Reset the selectedTaxInvoice to null or handle as needed
      this.selectedTaxInvoice = null;
    }
  }






 

  getMaterialForName(client_code){
    const materialIndex = this.clients.findIndex(
      (material) => material.client_code === client_code
    );
    if (materialIndex !== -1) {
      this.materialForName = this.clients[materialIndex].LglNm;
    } else {
      this.materialForName = 'Own';
    }


    console.log(this.materialForName);


  }




















}
