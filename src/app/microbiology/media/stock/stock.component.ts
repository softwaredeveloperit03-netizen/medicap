import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-stock',
  templateUrl: './stock.component.html',
  styleUrls: ['./stock.component.css'],
  providers: [DatePipe],
})
export class StockComponent implements OnInit {
  from_date = '';
  to_date = '';
  results;
  labours;
  balances;
  isOpening = false;
  medias;
  vendors;
  units;
  unit: any;
  vendor_name: any;
  flag = false;
  flag1 = false;
  flag2 = false;
  flag3 = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMedia();
    this.getMediaMaster();
     this.getVendor();
    this.get_rights();
   }
  selectedFile: File;
  isUpload = 0;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUpload = 1;
  }
 
  openlic(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
 

  getVendor() {

    this.service.get('microbiology/media.php?type=getApprovedVendor&vendor_type=Manufacturer').subscribe(response => {
      this.Manufacturers = response;
    });

    this.service.get('microbiology/media.php?type=getApprovedVendor&vendor_type=Supplier').subscribe(response => {
      this.Suppliers = response;
    });

  }
  Manufacturers;
  Suppliers;


  getMediaMaster() {
    this.service.get('master/media.php?type=getMedia').subscribe((response) => {
      this.medias = response;
    });
  }



  getMedia() {
    this.service.get('microbiology/media.php?type=getDehydratedMediaStock&from_date=' +this.from_date +'&to_date=' +this.to_date).subscribe((response) => {
        this.results = response;
      });
  }


  downloadRpt(id) {
    this.service.open(
      'microbiology/media.php?type=downloadMediaPrepartion_new&id=' + id
    );
  }
  selecteMedia(index){
    this.unit = this.medias[index-1].unit;
  }

 

  download() {
    this.service.open(
      'microbiology/media.php?type=downloadMediaStock&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
  specifVol = 1000;

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append('signture', this.selectedFile, this.selectedFile.name);
    }
    this.service
      .post('microbiology/media.php?type=saveMediaStock', uploadData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isOpening = false;
          this.getMedia();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  // -----------------------------------------12th july------------------------------------------//

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

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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
  //---------------------------------------------------------------------------------//

  onValueChange1(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag = false;
    } else {
      this.flag = true;
    }
  }

  onValueChange2(newValue: string) {
    const receivedQty = /^\d+$/;
    const isValidReceivedQty = receivedQty.test(newValue);

    if (isValidReceivedQty) {
      this.flag1 = false;
    } else {
      this.flag1 = true;
    }
  }

  onValueChange3(newValue: string) {
    const receivedQty = /^\d+$/;
    const isValidReceivedQty = receivedQty.test(newValue);

    if (isValidReceivedQty) {
      this.flag2 = false;
    } else {
      this.flag2 = true;
    }
  }
  onValueChange4(newValue: string) {
    const receivedQty = /^\d+$/;
    const isValidReceivedQty = receivedQty.test(newValue);

    if (isValidReceivedQty) {
      this.flag3 = false;
    } else {
      this.flag3 = true;
    }
  }
}
