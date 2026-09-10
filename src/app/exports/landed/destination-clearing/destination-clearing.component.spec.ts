import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DestinationClearingComponent } from './destination-clearing.component';

describe('DestinationClearingComponent', () => {
  let component: DestinationClearingComponent;
  let fixture: ComponentFixture<DestinationClearingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DestinationClearingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DestinationClearingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
