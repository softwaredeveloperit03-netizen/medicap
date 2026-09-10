import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawbulkComponent } from './rawbulk.component';

describe('RawbulkComponent', () => {
  let component: RawbulkComponent;
  let fixture: ComponentFixture<RawbulkComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawbulkComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RawbulkComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
