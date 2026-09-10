import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StocktransinComponent } from './stocktransin.component';

describe('StocktransinComponent', () => {
  let component: StocktransinComponent;
  let fixture: ComponentFixture<StocktransinComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StocktransinComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StocktransinComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
