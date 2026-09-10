import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PoPaymentLogComponent } from './po-payment-log.component';

describe('PoPaymentLogComponent', () => {
  let component: PoPaymentLogComponent;
  let fixture: ComponentFixture<PoPaymentLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PoPaymentLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PoPaymentLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
