import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BatchplanComponent } from './batchplan.component';

describe('BatchplanComponent', () => {
  let component: BatchplanComponent;
  let fixture: ComponentFixture<BatchplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BatchplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BatchplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
