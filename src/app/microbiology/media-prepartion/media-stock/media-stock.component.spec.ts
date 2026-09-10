import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MediaStockComponent } from './media-stock.component';

describe('MediaStockComponent', () => {
  let component: MediaStockComponent;
  let fixture: ComponentFixture<MediaStockComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MediaStockComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MediaStockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
