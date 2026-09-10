import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { of } from 'rxjs';

import { AwatingproceedcheckComponent } from './awatingproceedcheck.component';
import { DataService } from '../data.service';
import { DataAccessService } from 'src/app/data-access.service';
import { BmrFormStorageService } from '../bmr-form-storage.service';

describe('AwatingproceedcheckComponent', () => {
  let component: AwatingproceedcheckComponent;
  let fixture: ComponentFixture<AwatingproceedcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RouterTestingModule],
      declarations: [AwatingproceedcheckComponent],
      providers: [
        {
          provide: DataService,
          useValue: {
            getData: () => ({
              product_code: 'TEST',
              work_order_no: 'WO1',
              batch_number: 'B1',
              product_name: 'P',
            }),
          },
        },
        {
          provide: DataAccessService,
          useValue: {
            get: () => of([{ Stages: [] }]),
          },
        },
        {
          provide: BmrFormStorageService,
          useValue: {
            fetchServerSnapshot: () => of(null),
            getSnapshot: () => null,
            mergeServerAndLocal: (_l: any, s: any) => s,
            stepEntries: () => [],
            substepEntries: () => [],
            approveOnServer: () => of({ status: 'success' }),
            openPrintableReport: () => {},
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AwatingproceedcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
