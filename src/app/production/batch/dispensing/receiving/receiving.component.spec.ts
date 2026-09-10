import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ReceivingComponent } from './receiving.component';

describe('ReceivingComponent', () => {
  let component: ReceivingComponent;
  let fixture: ComponentFixture<ReceivingComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ReceivingComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ReceivingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
