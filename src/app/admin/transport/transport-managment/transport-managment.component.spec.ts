import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TransportManagmentComponent } from './transport-managment.component';

describe('TransportManagmentComponent', () => {
  let component: TransportManagmentComponent;
  let fixture: ComponentFixture<TransportManagmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TransportManagmentComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TransportManagmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
