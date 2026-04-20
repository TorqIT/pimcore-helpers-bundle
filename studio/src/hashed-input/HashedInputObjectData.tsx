import { injectable } from "inversify";
import type React from "react";
import { container } from "@pimcore/studio-ui-bundle";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { DynamicTypeObjectDataAbstractInput } from "@pimcore/studio-ui-bundle/modules/element";

@injectable()
export class HashedInputObjectData extends DynamicTypeObjectDataAbstractInput {
    id: string = "hashedInput";

    readonly dynamicTypeFieldFilterType: any = container.get(serviceIds["DynamicTypes/FieldFilter/String"]);

    getObjectDataComponent(props: any): React.ReactElement {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        return super.getObjectDataComponent(props);
    }

    getDefaultGridColumnWidth(): number | undefined {
        return 350;
    }
}
