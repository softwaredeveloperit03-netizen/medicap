import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PaymentbankComponent } from './paymentbank.component';

describe('PaymentbankComponent', () => {
  let component: PaymentbankComponent;
  let fixture: ComponentFixture<PaymentbankComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PaymentbankComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PaymentbankComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
