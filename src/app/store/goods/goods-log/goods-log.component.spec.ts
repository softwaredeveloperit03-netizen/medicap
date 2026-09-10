import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GoodsLogComponent } from './goods-log.component';

describe('GoodsLogComponent', () => {
  let component: GoodsLogComponent;
  let fixture: ComponentFixture<GoodsLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GoodsLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(GoodsLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
