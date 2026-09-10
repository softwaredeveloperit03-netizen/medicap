import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImpactreguComponent } from './impactregu.component';

describe('ImpactreguComponent', () => {
  let component: ImpactreguComponent;
  let fixture: ComponentFixture<ImpactreguComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImpactreguComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ImpactreguComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
