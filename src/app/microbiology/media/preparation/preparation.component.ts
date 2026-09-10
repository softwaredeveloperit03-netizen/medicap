import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preparation',
  templateUrl: './preparation.component.html',
  styleUrls: ['./preparation.component.css'],
  providers: [DatePipe],
})
export class PreparationComponent implements OnInit {
  from_date = '';
  to_date = '';
  results;
  lafs;
  today;
  balances;
  isNew = false;
  medias;
  selectedBatch = [];
  batches = [];
  qty_taken: number;
  flag1 = false;
  flag2 = false;
  flag3 = false;
  flag4 = false;
  flag5 = false;
  flag6 = false;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getMedia();
    this.getMediaMaster();
    this.get_rights();
  }



  Approvedmedias;


  getMediaMaster() {
    this.service.get('microbiology/media.php?type=getAvailableStockFORPREPARATION&status=Pending').subscribe((response) => {
      this.medias = response;
    });
  }

  getMediaMasterApp() {
    this.service.get('microbiology/media.php?type=getAvailableStockFORPREPARATION&status=Approved').subscribe((response) => {
      this.Approvedmedias = response;
    });
  }



  getMedia() {
    this.service.get('microbiology/media.php?type=getMediaPrepartion&from_date=' +this.from_date +'&to_date=' +this.to_date).subscribe((response) => {
        this.results = response;
      });
  }

  getBatch(index) {
    this.batches =[];
    this.selectedMedia =[];
    index = index - 1;
    if (index !== -1) {
      this.selectedMedia = this.medias[index].unit;

      let BATCHES = this.medias[index];
      this.batches = BATCHES['batches'];
    }
  }

  getBatch1(index) {
    this.batches =[];
    this.selectedMedia =[];
    index = index - 1;
    if (index !== -1) {
      this.selectedMedia = this.Approvedmedias[index].unit;

      let BATCHES = this.Approvedmedias[index];
      this.batches = BATCHES['batches'];
    }
  }




  getDetails(index) {
    this.selectedBatch =[];
    index = index - 1;
    if (index !== -1) {
      this.selectedBatch = this.batches[index];
    }
  }


  selectedMedia=[];
  volume_prepared =0;
  volume_unit = '';
  qty_per_containers ;
 
  compare(value) {
    this.qty_per_containers = 0;
    this.qty_per_containers = (Number(this.volume_prepared) / Number(value)).toFixed(2);
  }

  download() {
    this.service.open('microbiology/media.php?type=downloadMediaPrepartion&from_date=' +this.from_date +'&to_date=' +this.to_date);
  }





  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (+this.selectedBatch['avbl_qty'] < +data.value['qty_taken']) {
      alertify.error('Stock not available');
      return;
    }

    let temp = data.value;
    temp['mediaFor'] = 'GPT';
    this.service.post('microbiology/media.php?type=saveMediaPrepartion',
        JSON.stringify(data.value) ).subscribe((response) => {
        if (response['status'] === 'success') {
          this.getMedia();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }

  save1(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (+this.selectedBatch['avbl_qty'] < +data.value['qty_taken']) {
      alertify.error('Stock not available');
      return;
    }
    let temp = data.value;
    temp['mediaFor'] = 'OTHERS';

    this.service.post('microbiology/media.php?type=saveMediaPrepartion',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          this.getMedia();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }



  downloadRpt(id) {
    this.service.open(
      'microbiology/media.php?type=downloadMediaPrepartion_new&id=' + id
    );
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
      this.flag1 = false;
    } else {
      this.flag1 = true;
    }
  }

  onValueChange2(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag2 = false;
    } else {
      this.flag2 = true;
    }
  }

  onValueChange3(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag3 = false;
    } else {
      this.flag3 = true;
    }
  }

  onValueChange4(newValue) {
   
    this.qty_taken =0;
    
    let bhagela = Number(this.selectedBatch['specif_weight']) * newValue ;


    this.qty_taken =   bhagela / Number(this.selectedBatch['specifVol']) ;
 
  }

  onValueChange5(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag5 = false;
    } else {
      this.flag5 = true;
    }
  }

  onValueChange6(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);

    if (isValidBatchNo) {
      this.flag6 = false;
    } else {
      this.flag6 = true;
    }
  }
}
