import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClosingStockComponent } from './closing-stock.component';

describe('ClosingStockComponent', () => {
  let component: ClosingStockComponent;
  let fixture: ComponentFixture<ClosingStockComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClosingStockComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ClosingStockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
