import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BulkplanComponent } from './bulkplan.component';

describe('BulkplanComponent', () => {
  let component: BulkplanComponent;
  let fixture: ComponentFixture<BulkplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BulkplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BulkplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
