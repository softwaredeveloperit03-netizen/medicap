import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ListScrapMaterialComponent } from './list-scrap-material.component';

describe('ListScrapMaterialComponent', () => {
  let component: ListScrapMaterialComponent;
  let fixture: ComponentFixture<ListScrapMaterialComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ListScrapMaterialComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ListScrapMaterialComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
