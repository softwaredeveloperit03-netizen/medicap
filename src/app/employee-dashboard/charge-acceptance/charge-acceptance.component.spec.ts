import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChargeAcceptanceComponent } from './charge-acceptance.component';

describe('ChargeAcceptanceComponent', () => {
  let component: ChargeAcceptanceComponent;
  let fixture: ComponentFixture<ChargeAcceptanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChargeAcceptanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChargeAcceptanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
