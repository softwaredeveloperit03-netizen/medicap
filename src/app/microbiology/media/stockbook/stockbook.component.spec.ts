import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StockbookComponent } from './stockbook.component';

describe('StockbookComponent', () => {
  let component: StockbookComponent;
  let fixture: ComponentFixture<StockbookComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StockbookComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StockbookComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
